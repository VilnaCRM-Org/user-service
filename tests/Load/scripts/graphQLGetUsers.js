import http from 'k6/http';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLGetUsers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const users = insertUsersUtils.loadInsertedUsers()

export function setup() {
    return {
        users: users
    };
}

export const options = scenarioUtils.getOptions();

export default function getUsers(data) {
    let num = utils.getRandomNumber(1, data.users.length);

    const query = `
        query{
            users(first: ${num}){
                edges{
                    node{
                        id
                    }
                }
            }
        }`;

    const response = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    utils.checkResponse(
        response,
        'users returned',
        (res) => JSON.parse(res.body).data.users.edges.length === num
    );
}
