import InsertUsersUtils from './utils/insertUsersUtils.js';
import Utils from './utils/utils.js';
import file from 'k6/x/file';

const utils = new Utils();
const filepath = utils.getConfig()['usersFileLocation'] + utils.getConfig()['usersFileName']
const scenarioName = utils.getCLIVariable('scenarioName');
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    file.writeString(filepath, JSON.stringify(insertUsersUtils.prepareUsers()));
}

export const options = {
    setupTimeout: utils.getConfig().endpoints[scenarioName].setupTimeoutInMinutes + 'm',
    stages: [
        { duration: '1s', target: 1},
    ],
    insecureSkipTLSVerify: true,
    batchPerHost: utils.getConfig().batchSize,
}

export default function func(data) {

}
