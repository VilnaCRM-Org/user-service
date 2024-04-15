import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {Utils} from "./utils/utils.js";

const scenarioName = 'graphqlCreateUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function createUser() {
    const user = utils.generateUser();
    const mutationName = 'createUser';

    const mutation = `
     mutation {
        ${mutationName}(
            input: {
                email: "${user.email}"
                initials: "${user.initials}"
                password: "${user.password}"
            }
        ) {
            user {
                email
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
        'created user returned',
        (res) => JSON.parse(res.body).data[mutationName].user.email === `${user.email}`
    );
}
