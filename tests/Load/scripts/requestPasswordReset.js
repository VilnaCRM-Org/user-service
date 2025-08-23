import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'requestPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function requestPasswordReset() {
  const email = utils.generateRandomEmail();

  const response = utils.requestPasswordReset(email);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}