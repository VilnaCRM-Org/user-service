import file from 'k6/x/file';

import InsertUsersUtils from './insertUsersUtils.js';
import Utils from './utils.js';

const utils = new Utils();
const filepath = utils.getConfig()['usersFileLocation'] + utils.getConfig()['usersFileName'];
const scenarioName = utils.getCLIVariable('scenarioName');
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
  try {
    file.writeString(filepath, JSON.stringify(insertUsersUtils.prepareUsers()));
  } catch (error) {
    throw new Error(`Error occurred while writing users to ${filepath}: ${error.message}`);
  }
}

export const options = {
  setupTimeout: utils.getConfig().endpoints[scenarioName].setupTimeoutInMinutes + 'm',
  stages: [{ duration: '1s', target: 1 }],
  insecureSkipTLSVerify: true,
  batchPerHost: utils.getConfig().batchSize,
};

export default function func() {}
