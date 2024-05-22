import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import http from 'k6/http';

const scenarioName = 'createUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const batchSize = utils.getConfig().batchSize;

export const options = scenarioUtils.getOptions();

export default function createUser() {
    const batch = [];

    Array.from({ length: batchSize }).forEach(() => {
        batch.push(utils.generateUser());
    });

    const payload = JSON.stringify({
        'users': batch
    });

    const response = http.post(
        `${utils.getBaseHttpUrl()}/batch`,
        payload,
        utils.getJsonHeader()
    );

    utils.checkResponse(
        response,
        'is status 201',
        (res) => res.status === 201
    );
}
