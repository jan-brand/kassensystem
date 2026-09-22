<?php

namespace App\Foundation\Modules;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\Names;
use RuntimeException;

final class ModuleArtifactGenerator
{
    public const TYPES = ['model', 'action', 'query', 'service', 'event', 'listener', 'job', 'policy', 'request', 'exception', 'enum', 'contract', 'dto', 'migration', 'factory', 'seeder', 'command', 'controller'];

    public function plan(FilePlan $plan, string $module, string $type, string $name): FilePlan
    {
        $module = Names::studly($module);
        $type = strtolower($type);
        $name = Names::studly($name);
        $moduleDir = 'app/Modules/'.$module;
        if (! is_file(base_path($moduleDir.'/module.json'))) {
            throw new RuntimeException("Module {$module} does not exist.");
        }
        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unsupported type: '.$type);
        }

        [$dir, $class, $suffix] = match ($type) {
            'model' => ['Models', $name, ''],
            'action' => ['Actions', str_ends_with($name, 'Action') ? $name : $name.'Action', ''],
            'query' => ['Queries', $name, ''],
            'service' => ['Services', str_ends_with($name, 'Service') ? $name : $name.'Service', ''],
            'event' => ['Events', $name, ''],
            'listener' => ['Listeners', $name, ''],
            'job' => ['Jobs', str_ends_with($name, 'Job') ? $name : $name.'Job', ''],
            'policy' => ['Policies', str_ends_with($name, 'Policy') ? $name : $name.'Policy', ''],
            'request' => ['Http/Requests', str_ends_with($name, 'Request') ? $name : $name.'Request', ''],
            'exception' => ['Exceptions', $name, ''],
            'enum' => ['Enums', $name, ''],
            'contract' => ['Contracts', $name, ''],
            'dto' => ['DTOs', $name, ''],
            'migration' => ['database/migrations', $name, '.php'],
            'factory' => ['Database/Factories', str_ends_with($name, 'Factory') ? $name : $name.'Factory', ''],
            'seeder' => ['Database/Seeders', str_ends_with($name, 'Seeder') ? $name : $name.'Seeder', ''],
            'command' => ['Console/Commands', str_ends_with($name, 'Command') ? $name : $name.'Command', ''],
            'controller' => ['Http/Controllers', str_ends_with($name, 'Controller') ? $name : $name.'Controller', ''],
        };

        $path = $moduleDir.'/'.$dir.'/'.($type === 'migration' ? date('Y_m_d_His').'_'.$this->snake($name).'.php' : $class.'.php');
        $content = $this->content($module, $type, $class, $dir);

        return $plan->write($path, $content);
    }

    private function content(string $module, string $type, string $class, string $dir): string
    {
        if ($type === 'migration') {
            return "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n    public function up(): void { /* Schema changes */ }\n    public function down(): void { /* Reverse changes */ }\n};\n";
        }
        $ns = 'App\\Modules\\'.$module.'\\'.str_replace('/', '\\', $dir);

        return match ($type) {
            'model' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Database\\Eloquent\\Model;\n\nfinal class {$class} extends Model\n{\n    protected \$guarded = [];\n}\n",
            'action' => "<?php\n\nnamespace {$ns};\n\nfinal class {$class}\n{\n    public function execute(mixed ...\$arguments): mixed\n    {\n        // Perform one explicit state-changing use case.\n        return null;\n    }\n}\n",
            'query' => "<?php\n\nnamespace {$ns};\n\nfinal class {$class}\n{\n    public function execute(mixed ...\$arguments): mixed\n    {\n        // Read or derive data without changing application state.\n        return null;\n    }\n}\n",
            'service' => "<?php\n\nnamespace {$ns};\n\nfinal class {$class}\n{\n    // Reusable technical or domain service.\n}\n",
            'event' => "<?php\n\nnamespace {$ns};\n\nfinal readonly class {$class}\n{\n    public function __construct(public mixed \$payload = null) {}\n}\n",
            'listener' => "<?php\n\nnamespace {$ns};\n\nfinal class {$class}\n{\n    public function handle(object \$event): void {}\n}\n",
            'job' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Contracts\\Queue\\ShouldQueue;\nuse Illuminate\\Foundation\\Queue\\Queueable;\n\nfinal class {$class} implements ShouldQueue\n{\n    use Queueable;\n    public function handle(): void {}\n}\n",
            'policy' => "<?php\n\nnamespace {$ns};\n\nfinal class {$class}\n{\n    // Add authorization methods explicitly.\n}\n",
            'request' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Foundation\\Http\\FormRequest;\n\nfinal class {$class} extends FormRequest\n{\n    public function authorize(): bool { return true; }\n    public function rules(): array { return []; }\n}\n",
            'exception' => "<?php\n\nnamespace {$ns};\n\nuse RuntimeException;\n\nfinal class {$class} extends RuntimeException {}\n",
            'enum' => "<?php\n\nnamespace {$ns};\n\nenum {$class}: string\n{\n    case Example = 'example';\n}\n",
            'contract' => "<?php\n\nnamespace {$ns};\n\ninterface {$class} {}\n",
            'dto' => "<?php\n\nnamespace {$ns};\n\nfinal readonly class {$class}\n{\n    public function __construct(public mixed \$value = null) {}\n}\n",
            'factory' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Database\\Eloquent\\Factories\\Factory;\n\nfinal class {$class} extends Factory\n{\n    public function definition(): array { return []; }\n}\n",
            'seeder' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Database\\Seeder;\n\nfinal class {$class} extends Seeder\n{\n    public function run(): void {}\n}\n",
            'command' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Console\\Command;\n\nfinal class {$class} extends Command\n{\n    protected \$signature = '".strtolower($module).':'.$this->snake(preg_replace('/Command$/', '', $class) ?? $class)."';\n    protected \$description = 'Generated module command';\n    public function handle(): int { return self::SUCCESS; }\n}\n",
            'controller' => "<?php\n\nnamespace {$ns};\n\nuse Illuminate\\Http\\Response;\n\nfinal class {$class}\n{\n    public function __invoke(): Response { return response('OK'); }\n}\n",
            default => "<?php\n\nnamespace {$ns};\n\nfinal class {$class} {}\n",
        };
    }

    private function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value) ?? $value);
    }
}
