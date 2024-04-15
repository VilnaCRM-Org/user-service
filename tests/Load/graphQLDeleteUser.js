import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {Utils} from "./utils/utils.js";
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import counter from "k6/x/counter"

const scenarioName = 'graphqlDeleteUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function deleteUser(data) {
    const user = data.users[counter.up()];
    utils.checkUserIsDefined(user);

    const id = utils.getGraphQLIdPrefix() + user.id;
    const mutationName = 'deleteUser';

    const mutation = `
     mutation{
        ${mutationName} (input: {id: "${id}"}){
            user {
               id
            }
        }
     }`;

    const response = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: mutation}),
        utils.getJsonHeader(),
    );

    utils.checkResponse(
        response,
        'deleted user returned',
        (res) => JSON.parse(res.body).data[mutationName].user.id === `${id}`
    );
}
