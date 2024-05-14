import http from 'k6/http';
import MailCatcherUtils from './utils/mailCatcherUtils.js';
import ScenarioUtils from './utils/scenarioUtils.js';
import Utils from './utils/utils.js';

const scenarioName = 'graphQLConfirmUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default async function confirmUser() {
    const generatedUser = utils.generateUser();
    const { email } = generatedUser;
    const userResponse = await utils.registerUser(generatedUser);
    const user = JSON.parse(userResponse.body);
    const id = utils.getGraphQLIdPrefix() + user.id;
    const mutationName = 'confirmUser';

    let token = null;

    if (userResponse.status === 201) {
        token = await mailCatcherUtils.getConfirmationToken(email);
    }

    const mutation = `
     mutation {
        ${mutationName}(input: { token: "${token}" }) {
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

    console.log(response.body);

    utils.checkResponse(
        response,
        'confirmed user returned',
        (res) => JSON.parse(res.body).data[mutationName].user.id === `${id}`
    );
}
