import InsertUsersUtils from './insertUsersUtils.js';
import Utils from './utils.js';
import MailCatcherUtils from './mailCatcherUtils.js';
import file from 'k6/x/file';

const utils = new Utils();
const mailCatcherUtils = new MailCatcherUtils(utils);
const filepath = utils.getConfig()['usersFileLocation'] + utils.getConfig()['usersFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const scenariosRequiringFreshEmails = ['confirmUser', 'graphQLConfirmUser'];

export function setup() {
  try {
    if (scenariosRequiringFreshEmails.includes(scenarioName)) {
      mailCatcherUtils.clearMessages();
    }
    file.writeString(filepath, JSON.stringify(insertUsersUtils.prepareUsers()));
  } catch (error) {
    console.log(`Error occurred while writing users to ${filepath}: ${error}`);
    throw error;
  }
}

export const options = {
  setupTimeout: utils.getConfig().endpoints[scenarioName].setupTimeoutInMinutes + 'm',
  stages: [{ duration: '1s', target: 1 }],
  insecureSkipTLSVerify: true,
  batchPerHost: utils.getConfig().batchSize,
};

export default function func(data) {}
