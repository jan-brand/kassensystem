<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Enums\DigitalReceiptKind;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\DigitalReceipt;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleReversal;
use App\Modules\Settings\Models\SystemSetting;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class DigitalReceiptService
{
    private const TOKEN_LENGTH = 48;

    private const DEFAULT_RETENTION_DAYS = 365;

    public function __construct(private readonly GetSystemSettingsQuery $settings) {}

    public function ensureSaleReceipt(Sale $sale): DigitalReceipt
    {
        return DB::transaction(function () use ($sale): DigitalReceipt {
            $locked = Sale::query()
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if (
                $locked->status !== SaleStatus::Completed
                || $locked->number === null
                || $locked->completed_at === null
            ) {
                throw new LogicException('Digital receipts require a completed sale.');
            }

            $existing = DigitalReceipt::query()
                ->where('sale_id', $locked->id)
                ->where('kind', DigitalReceiptKind::Sale->value)
                ->first();

            if ($existing instanceof DigitalReceipt) {
                return $existing;
            }

            return $this->createReceipt(
                sale: $locked,
                kind: DigitalReceiptKind::Sale,
                issuedBasis: $locked->completed_at,
                reversalId: null,
            );
        });
    }

    public function ensureReversalReceipt(SaleReversal $reversal): DigitalReceipt
    {
        return DB::transaction(function () use ($reversal): DigitalReceipt {
            $lockedReversal = SaleReversal::query()
                ->lockForUpdate()
                ->findOrFail($reversal->id);
            $sale = Sale::query()
                ->lockForUpdate()
                ->findOrFail($lockedReversal->sale_id);

            if (
                $sale->status !== SaleStatus::Completed
                || $sale->number === null
                || $sale->completed_at === null
            ) {
                throw new LogicException('Reversal receipts require a completed sale.');
            }

            $existing = DigitalReceipt::query()
                ->where('sale_id', $sale->id)
                ->where('kind', DigitalReceiptKind::Reversal->value)
                ->first();

            if ($existing instanceof DigitalReceipt) {
                return $existing;
            }

            return $this->createReceipt(
                sale: $sale,
                kind: DigitalReceiptKind::Reversal,
                issuedBasis: $lockedReversal->reversed_at,
                reversalId: $lockedReversal->id,
            );
        });
    }

    public function publicUrl(DigitalReceipt $receipt): string
    {
        $token = Crypt::decryptString($receipt->token_ciphertext);

        return route('sales.receipt.public', ['token' => $token]);
    }

    public function resolvePublic(string $token): ?DigitalReceipt
    {
        if (! preg_match('/^[A-Za-z0-9]{48}$/D', $token)) {
            return null;
        }

        $receipt = DigitalReceipt::query()
            ->with([
                'sale.items',
                'sale.payments',
                'sale.reversal',
                'reversal',
            ])
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $receipt instanceof DigitalReceipt || $receipt->isExpired()) {
            return null;
        }

        return $receipt;
    }

    private function createReceipt(
        Sale $sale,
        DigitalReceiptKind $kind,
        Carbon $issuedBasis,
        ?int $reversalId,
    ): DigitalReceipt {
        if ($sale->number === null) {
            throw new LogicException('Sale number is required for a digital receipt.');
        }

        $settings = $this->settings->execute();
        $retentionDays = $settings instanceof SystemSetting
            ? $settings->receipt_retention_days
            : self::DEFAULT_RETENTION_DAYS;
        $merchantName = $settings instanceof SystemSetting && trim($settings->cafeteria_name) !== ''
            ? trim($settings->cafeteria_name)
            : (string) config('app.name', 'Kassensystem');
        $currency = strtoupper((string) config('kassensystem.currency', 'EUR'));

        if ($retentionDays < 1 || $retentionDays > 3650) {
            throw new LogicException('Invalid digital receipt retention configuration.');
        }

        if (! preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new LogicException('Invalid receipt currency configuration.');
        }

        do {
            $token = Str::random(self::TOKEN_LENGTH);
            $tokenHash = hash('sha256', $token);
        } while (DigitalReceipt::query()->where('token_hash', $tokenHash)->exists());

        return DigitalReceipt::query()->create([
            'sale_id' => $sale->id,
            'sale_reversal_id' => $reversalId,
            'kind' => $kind,
            'token_hash' => $tokenHash,
            'token_ciphertext' => Crypt::encryptString($token),
            'merchant_name_snapshot' => $merchantName,
            'sale_number_snapshot' => $sale->number,
            'currency_snapshot' => $currency,
            'issued_at' => now(),
            'expires_at' => $issuedBasis->copy()->addDays($retentionDays),
        ]);
    }
}
