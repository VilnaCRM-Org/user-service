import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import http from 'k6/http';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'createUserBatch';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const batchSize = utils.getConfig().endpoints[scenarioName].batchSize;
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function createUser() {
  const generator = insertUsersUtils.usersGenerator(batchSize);
  const batch = [];
  const usedEmails = new Set();
  const maxRetries = 5;

  for (let userIndex = 0; userIndex < batchSize; userIndex++) {
    let user;
    let retryCount = 0;

    do {
      user = generator.next().value;
      retryCount++;

      if (retryCount > maxRetries) {
        console.error(
          `Failed to generate unique email after ${maxRetries} attempts for user ${userIndex}`
        );
        const fallbackEmail = `user_fallback_${userIndex}_${Date.now()}_${Math.random().toString(36).substring(2)}@example.com`;
        user.email = fallbackEmail;
        break;
      }
    } while (usedEmails.has(user.email));

    usedEmails.add(user.email);
    batch.push(user);
  }

  const payload = JSON.stringify({
    users: batch,
  });

  const response = http.post(`${utils.getBaseHttpUrl()}/batch`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
