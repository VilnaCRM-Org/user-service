const { run, analyze, StringAnalysis, BrowserInteractionResultReader } = require('@memlab/api');

(async function () {
  // TODO: in progress, its not final solution
  const scenarioOne = require('./tests/toggleMobileMenu');
  const scenarioTwo = require('./tests/sliderScroll');

  await run({
    scenario: scenarioOne,
    consoleMode: 'VERBOSE',
    workDir: './src/test/memory-leak/results',
  });
  await run({
    scenario: scenarioTwo,
    consoleMode: 'VERBOSE',
    workDir: './src/test/memory-leak/results',
  });

  const dataDir = '/src/test/memory-leak/results/data';
  const results = BrowserInteractionResultReader.from(dataDir);

  const analysis = new StringAnalysis();
  await analyze(results, analysis);
})();
