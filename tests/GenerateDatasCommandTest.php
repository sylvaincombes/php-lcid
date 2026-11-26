<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use SylvainCombes\Lcid\Command\GenerateDatasCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Group('command')]
class GenerateDatasCommandTest extends TestCase
{
    private const UPSTREAM_JSON = '{"1033":"en_US","1036":"fr_FR","4":"zh_CHS"}';

    private string $workDir;
    private string $localJsonPath;
    private string $manualDatasPath;

    #[Group('command')]
    public function testCommandIsRegisterableAndNamed(): void
    {
        $application = new Application();
        $application->addCommand($this->buildCommand(self::UPSTREAM_JSON));

        $this->assertTrue($application->has('lcid:generate-datas'));
    }

    #[Group('command')]
    public function testGeneratesMappedAndFallbacksFromUpstreamJson(): void
    {
        $tester = $this->runCommand(self::UPSTREAM_JSON);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $datas = $this->readLocalDatas();
        $this->assertSame(1036, $datas['mapped']['fr_FR']);
        $this->assertSame(1033, $datas['mapped']['en_US']);
        $this->assertSame([4], $datas['fallbacks']['zh']);
    }

    #[Group('command')]
    public function testManualDatasAddMissingLocale(): void
    {
        file_put_contents($this->manualDatasPath, <<<'PHP'
            <?php

            return [
                ['name' => 'French (Canada)', 'language' => 'fr', 'locale' => 'fr_CA', 'lcid' => 3084],
            ];
            PHP);

        $this->runCommand(self::UPSTREAM_JSON);

        $this->assertSame(3084, $this->readLocalDatas()['mapped']['fr_CA']);
    }

    #[Group('command')]
    public function testGenerationIsIdempotent(): void
    {
        $this->runCommand(self::UPSTREAM_JSON);
        $firstRun = (string) file_get_contents($this->localJsonPath);

        $this->runCommand(self::UPSTREAM_JSON);

        $this->assertSame($firstRun, (string) file_get_contents($this->localJsonPath));
    }

    #[Group('command')]
    public function testFailsWhenDistantDatasCannotBeFetched(): void
    {
        $tester = new CommandTester($this->buildCommand(false));

        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Error fetching distant datas', $tester->getDisplay());
        $this->assertFileDoesNotExist($this->localJsonPath);
    }

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir().'/lcid-command-'.bin2hex(random_bytes(4));
        mkdir($this->workDir);
        $this->localJsonPath = $this->workDir.'/datas.json';
        $this->manualDatasPath = $this->workDir.'/datas-manual.php';
        file_put_contents($this->manualDatasPath, "<?php\n\nreturn [];\n");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->workDir.'/*') ?: []);
        rmdir($this->workDir);
    }

    private function buildCommand(string|false $distantJson): GenerateDatasCommand
    {
        return new GenerateDatasCommand(
            static fn (string $url): string|false => $distantJson,
            $this->localJsonPath,
            $this->manualDatasPath,
        );
    }

    private function runCommand(string $distantJson): CommandTester
    {
        $tester = new CommandTester($this->buildCommand($distantJson));
        $tester->execute([]);

        return $tester;
    }

    /** @return array{mapped: array<string, int>, fallbacks: array<string, list<int>>} */
    private function readLocalDatas(): array
    {
        /** @var array{mapped: array<string, int>, fallbacks: array<string, list<int>>} */
        return json_decode((string) file_get_contents($this->localJsonPath), true, 512, JSON_THROW_ON_ERROR);
    }
}
