<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Class AutoloaderTest
 */
class HandlebarsTest extends TestCase
{
	/**
	 * Test handlebars autoloader
	 *
	 * @return void
	 */
	public function testAutoLoad()
	{
		Handlebars\Autoloader::register(realpath(__DIR__ . '/../fixture/'));

		$this->assertTrue(class_exists('Handlebars\\Test'));
		$this->assertTrue(class_exists('Handlebars\\Example\\Test'));
	}

	/**
	 * sanity test case 0.
	 *
	 * @test
	 */
	public function sanityTest0()
	{
		$loader = new \Handlebars\Loader\StringLoader();
		$engine = new \Handlebars\Handlebars(array('loader' => $loader));

		$stressTestCase = $this->loadTestCase(__DIR__ . '/../fixture/testCases/case0');

		$startTime = microtime(true);
		$result = $engine->render(
			$stressTestCase->getSrcContent(),
			$stressTestCase->getData()
		);
		$endTime = microtime(true);

		echo 'Total Time: ' . ($endTime - $startTime) . ' ms' . "\n";

		// validate result.
		$this->assertEquals($stressTestCase->getExpectedResult(), $result);
	}

	/**
	 * Stress test case 1.
	 *
	 * @test
	 */
	public function stressTest1()
	{
		$timesToRun = 10000;

		$loader = new \Handlebars\Loader\StringLoader();
		$engine = new \Handlebars\Handlebars(array('loader' => $loader));

		$stressTestCase = $this->loadTestCase(__DIR__ . '/../fixture/testCases/case1');

		$startTime = microtime(true);
		while ($timesToRun-- > 0) {
			$result = $engine->render(
				$stressTestCase->getSrcContent(),
				$stressTestCase->getData()
			);
		}
		$endTime = microtime(true);

		echo 'Total Time: ' . ($endTime - $startTime) . ' ms' . "\n";

		file_put_contents("/tmp/out.txt", $result);

		// validate result.
		$this->assertEquals($stressTestCase->getExpectedResult(), $result);
	}

	private function loadTestCase(string $path) : StressTestCase
	{
		$src = file_get_contents($path .'/case.handlebars');
		$data = file_get_contents($path . '/case.data');
		$expectedResult = file_get_contents($path . '/case.expected');

		return new StressTestCase($src, $data, $expectedResult);
	}
}

class StressTestCase
{
	/**
	 * @var string $srcContent Source Content.
	 */
	private $srcContent;

	/**
	 * @var string $dataContent variable replacement data.
	 */
	private $dataContent;

	/**
	 * @var string[] parsed data content.
	 */
	private $parsedData = [];

	/**
	 * @var string expected rendered result.
	 */
	private $expectedResult;

	/**
	 * TestCase constructor.
	 * @param string $srcContent
	 * @param string $dataContent
	 * @param string $expectedResult
	 */
	public function __construct(string $srcContent, string $dataContent, string $expectedResult)
	{
		$this->srcContent = $srcContent;
		$this->dataContent = $dataContent;
		$this->expectedResult = $expectedResult;

		$lines = explode("\n", $this->dataContent);
		foreach ($lines as $line) {
			$bits = explode("=", $line);
			$this->assignArrayByPath($this->parsedData, $bits[0], $bits[1], '.');
		}
	}

	private function assignArrayByPath(&$arr, $path, $value, $separator='.') : void
	{
		$keys = explode($separator, $path);

		foreach ($keys as $key) {
			$arr = &$arr[$key];
		}

		$arr = $value;
	}

	/**
	 * @return string
	 */
	public function getSrcContent(): string
	{
		return $this->srcContent;
	}

	/**
	 * @return array
	 */
	public function getData() : array
	{
		return $this->parsedData;
	}

	/**
	 * @return string
	 */
	public function getExpectedResult(): string
	{
		return $this->expectedResult;
	}
}