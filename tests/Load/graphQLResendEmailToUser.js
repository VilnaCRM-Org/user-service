import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import counter from "k6/x/counter"

const utils = new Utils();
const scenarioName = 'graphqlResendEmailToUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    resendEmail(data.users[counter.up()]);
}

function resendEmail(user) {
    utils.checkUserIsDefined(user);

    const id = utils.getGraphQLIdPrefix() + user.id;
    const mutationName = 'resendEmailToUser';

    const mutation = `
     mutation{
  ${mutationName}(input:{id:"${id}"}){
    user{
      id
    }
  }
}`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: mutation}),
        utils.getJsonHeader(),
    );

    check(res, {
        'user returned': (r) =>
            JSON.parse(r.body).data[mutationName].user.id === `${id}`,
    });
}
