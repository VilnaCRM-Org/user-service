const fs = require('node:fs');

const { run, analyze } = require('@memlab/api');
const { StringAnalysis } = require('@memlab/heap-analysis');

const memoryLeakDir = './src/test/memory-leak';
const testsDir = './tests';

const workDir = './src/test/memory-leak/results';
const consoleMode = 'VERBOSE';

(async function () {
  const testFilePaths = fs
    .readdirSync(`${memoryLeakDir}/${testsDir}`)
    .map(test => `${testsDir}/${test}`);

  for (const testFilePath of testFilePaths) {
    const scenario = require(testFilePath);

    const { runResult } = await run({
      scenario,
      consoleMode,
      workDir,
    });

    const analyzer = new StringAnalysis();
    await analyze(runResult, analyzer);

    runResult.cleanup();
  }
})();
