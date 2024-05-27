const { run } = require('@memlab/api');

const tests = ['./tests/toggleMobileMenu', './tests/sliderScroll'];

// TODO: make all tests run by one command
(async function () {
  for (const testPath of tests) {
    const test = require(testPath);
    const { runResult } = await run({
      url: test.url,
      action: test.action,
      back: test.back,
    });
  }
})();
