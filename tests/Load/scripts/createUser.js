import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'createUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function createUser() {
  const response = utils.registerUser(utils.generateUser());

  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
