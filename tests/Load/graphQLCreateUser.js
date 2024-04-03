import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";

const utils = new Utils();
const scenarioName = 'graphqlCreateUser';
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function () {
    createUser();
}

function createUser() {
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);
    const mutationName = 'createUser';

    const mutation = `
     mutation {
  ${mutationName}(
    input: {
      email: "${email}"
      initials: "${initials}"
      password: "${password}"
    }
  ) {
    user {
      email
    }
  }
}`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: mutation}),
        utils.getJsonHeader(),
    );

    check(res, {
        'created user returned': (r) =>
            JSON.parse(r.body).data[mutationName].user.email === `${email}`,
    });
}
