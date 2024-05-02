import http from 'k6/http';
import counter from 'k6/x/counter';
import exec from 'k6/x/exec';

import ScenarioUtils from './utils/scenarioUtils.js';
import Utils from './utils/utils.js';

const scenarioName = 'getUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

const runSmoke = utils.getCLIVariable('run_smoke') || 'true';
const runAverage = utils.getCLIVariable('run_average') || 'true';
const runStress = utils.getCLIVariable('run_stress') || 'true';
const runSpike = utils.getCLIVariable('run_spike') || 'true';
exec.command(
    "make",
    [
        `SCENARIO_NAME=${scenarioName}`,
        `RUN_SMOKE=${runSmoke}`,
        `RUN_AVERAGE=${runAverage}`,
        `RUN_STRESS=${runStress}`,
        `RUN_SPIKE=${runSpike}`,
        `load-tests-prepare-users`,
    ]);
const users = JSON.parse(open('users.json'));

export function setup() {
    return {
        users: users,
    };
}

export const options = scenarioUtils.getOptions();

export default function getUser(data) {
    const user = data.users[counter.up()];
    utils.checkUserIsDefined(user);

    const {id} = user;

    const response = http.get(
        `${utils.getBaseHttpUrl()}/${id}`,
        utils.getJsonHeader()
    );

    utils.checkResponse(
        response,
        'is status 200',
        (res) => res.status === 200
    );
}
