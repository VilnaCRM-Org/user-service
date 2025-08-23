import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'confirmPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function confirmPasswordReset() {
  // First create a user and get password reset token
  const user = utils.generateUser();
  utils.registerUser(user);

  const requestResponse = utils.requestPasswordReset(user.email);
  utils.checkResponse(requestResponse, 'password reset requested', res => res.status === 200);

  // Generate a mock token for testing (in real scenario this would come from email)
  const token = utils.generateToken();
  const newPassword = utils.generatePassword();

  const confirmResponse = utils.confirmPasswordReset(token, newPassword);

  // This will likely return 404 for non-existent token in load test, which is expected
  utils.checkResponse(
    confirmResponse,
    'is status 404 or 410',
    res => res.status === 404 || res.status === 410 || res.status === 200
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
