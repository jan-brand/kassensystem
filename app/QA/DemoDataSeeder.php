<?php

namespace App\QA;

use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\RecordCashWithdrawalAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Settings\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use LogicException;

final class DemoDataSeeder
{
    private const ACCOUNTS = [
        [
            'role' => UserRole::Administrator,
            'username' => 'demo-admin',
            'pin' => '910001',
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'display_name' => 'Demo Admin',
        ],
        [
            'role' => UserRole::Manager,
            'username' => 'demo-manager',
            'pin' => '910002',
            'first_name' => 'Demo',
            'last_name' => 'Manager',
            'display_name' => 'Demo Manager',
        ],
        [
            'role' => UserRole::Cashier,
            'username' => 'demo-kasse',
            'pin' => '910003',
            'first_name' => 'Demo',
            'last_name' => 'Kasse',
            'display_name' => 'Demo Kasse',
        ],
    ];

    private const CATALOG = [
        [
            'name' => 'Kaffee & Tee',
            'sort_order' => 10,
            'products' => [
                ['name' => 'Kaffee', 'short_name' => 'Kaffee', 'price_cents' => 100, 'sort_order' => 10],
                ['name' => 'Kaffee Kanne', 'short_name' => 'Kanne', 'price_cents' => 300, 'sort_order' => 20],
                ['name' => 'Tee', 'short_name' => 'Tee', 'price_cents' => 50, 'sort_order' => 30],
                ['name' => 'Tee Premium', 'short_name' => 'Tee Premium', 'price_cents' => 100, 'sort_order' => 40],
            ],
        ],
        [
            'name' => 'Wasser & Saft',
            'sort_order' => 20,
            'products' => [
                ['name' => 'Wasser', 'short_name' => 'Wasser', 'price_cents' => 100, 'sort_order' => 10],
                ['name' => 'Apfelschorle', 'short_name' => 'Apfelschorle', 'price_cents' => 150, 'sort_order' => 20],
                ['name' => 'Orangensaft', 'short_name' => 'O-Saft', 'price_cents' => 150, 'sort_order' => 30],
                ['name' => 'Gratis Wasser', 'short_name' => 'Gratis Wasser', 'price_cents' => 0, 'sort_order' => 40],
            ],
        ],
        [
            'name' => 'Brötchen',
            'sort_order' => 30,
            'products' => [
                ['name' => 'Käsebrötchen', 'short_name' => 'Käse', 'price_cents' => 250, 'sort_order' => 10],
                ['name' => 'Laugenstange', 'short_name' => 'Lauge', 'price_cents' => 180, 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Kuchen & Gebäck',
            'sort_order' => 40,
            'products' => [
                ['name' => 'Muffin', 'short_name' => 'Muffin', 'price_cents' => 200, 'sort_order' => 10],
                ['name' => 'Kuchen', 'short_name' => 'Kuchen', 'price_cents' => 250, 'sort_order' => 20],
            ],
        ],
        [
            'name' => 'Warmes',
            'sort_order' => 50,
            'products' => [
                ['name' => 'Suppe', 'short_name' => 'Suppe', 'price_cents' => 350, 'sort_order' => 10],
                ['name' => 'Toast', 'short_name' => 'Toast', 'price_cents' => 300, 'sort_order' => 20],
            ],
        ],
    ];

    public function __construct(
        private readonly OpenCashSessionAction $openCashSession,
        private readonly RecordCashDepositAction $recordDeposit,
        private readonly RecordCashWithdrawalAction $recordWithdrawal,
        private readonly StartCashSessionClosingAction $startClosing,
        private readonly CloseCashSessionAction $closeCashSession,
        private readonly StartSaleAction $startSale,
        private readonly AddProductToSaleAction $addProduct,
        private readonly CompleteCashSaleAction $completeSale,
    ) {}

    /**
     * @return array{
     *     users: int,
     *     categories: int,
     *     products: int,
     *     register: string,
     *     history_seeded: bool
     * }
     */
    public function seed(bool $withHistory = true): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo data may only be seeded in local or testing environments.');
        }

        $users = $this->seedUsers();
        $products = $this->seedCatalog();

        $register = Register::query()->updateOrCreate(
            ['code' => 'register-1'],
            [
                'name' => 'Kasse 1',
                'active' => true,
            ],
        );

        SystemSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'cafeteria_name' => 'Demo Cafeteria',
                'logo_path' => null,
                'pos_show_short_names' => true,
                'updated_by_user_id' => $users['demo-admin']->id,
            ],
        );

        $historySeeded = $withHistory
            ? $this->seedHistory($register, $users['demo-kasse'], $products)
            : false;

        return [
            'users' => count($users),
            'categories' => Category::query()->whereIn(
                'name',
                array_column(self::CATALOG, 'name'),
            )->count(),
            'products' => count($products),
            'register' => $register->name,
            'history_seeded' => $historySeeded,
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function credentialRows(): array
    {
        return array_map(
            static fn (array $account): array => [
                $account['role']->value,
                $account['username'],
                $account['pin'],
            ],
            self::ACCOUNTS,
        );
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $users = [];

        foreach (self::ACCOUNTS as $account) {
            $user = User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'pin_hash' => Hash::make($account['pin']),
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'display_name' => $account['display_name'],
                    'email' => null,
                    'phone' => null,
                    'role' => $account['role'],
                    'active' => true,
                    'pin_changed_at' => now(),
                ],
            );

            $users[$user->username] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, Product>
     */
    private function seedCatalog(): array
    {
        $products = [];

        foreach (self::CATALOG as $categoryData) {
            $category = Category::query()->firstOrCreate(
                ['name' => $categoryData['name']],
                [
                    'active' => true,
                    'sort_order' => $categoryData['sort_order'],
                ],
            );

            $category->update([
                'active' => true,
                'sort_order' => $categoryData['sort_order'],
            ]);

            foreach ($categoryData['products'] as $productData) {
                $product = Product::query()->updateOrCreate(
                    ['name' => $productData['name']],
                    [
                        'category_id' => $category->id,
                        'short_name' => $productData['short_name'],
                        'price_cents' => $productData['price_cents'],
                        'active' => true,
                        'sort_order' => $productData['sort_order'],
                    ],
                );

                $products[$product->name] = $product;
            }
        }

        return $products;
    }

    /**
     * @param array<string, Product> $products
     */
    private function seedHistory(Register $register, User $cashier, array $products): bool
    {
        $hasDemoSales = Sale::query()
            ->where('cashier_id', $cashier->id)
            ->where('status', SaleStatus::Completed->value)
            ->exists();

        if ($hasDemoSales) {
            return false;
        }

        $hasActiveSession = CashSession::query()
            ->where('register_id', $register->id)
            ->whereIn('status', [
                CashSessionStatus::Open->value,
                CashSessionStatus::Closing->value,
            ])
            ->exists();

        if ($hasActiveSession) {
            return false;
        }

        $session = $this->openCashSession->execute($register, $cashier, 5000);

        $this->recordDeposit->execute(
            $session,
            $cashier,
            1000,
            'Demo: Wechselgeld aufgefüllt',
        );

        $sale = $this->startSale->execute($register, $session, $cashier);
        $this->addProduct->execute($sale, $products['Kaffee']);
        $this->addProduct->execute($sale, $products['Kaffee']);
        $sale = $this->addProduct->execute($sale, $products['Käsebrötchen']);
        $this->completeSale->execute($sale, 500);

        $sale = $this->startSale->execute($register, $session, $cashier);
        $this->addProduct->execute($sale, $products['Apfelschorle']);
        $sale = $this->addProduct->execute($sale, $products['Muffin']);
        $this->completeSale->execute($sale, 500);

        $sale = $this->startSale->execute($register, $session, $cashier);
        $sale = $this->addProduct->execute($sale, $products['Gratis Wasser']);
        $this->completeSale->execute($sale, 0);

        $this->recordWithdrawal->execute(
            $session,
            $cashier,
            500,
            'Demo: Bargeld entnommen',
        );

        $closing = $this->startClosing->execute($session, $cashier);
        $expectedCash = $closing->expectedCashCents();

        $this->closeCashSession->execute(
            $closing,
            $cashier,
            $expectedCash,
            'Automatisch erzeugter Demo-Abschluss',
        );

        return true;
    }
}
