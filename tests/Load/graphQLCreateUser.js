import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import faker from "k6/x/faker";
import {check} from 'k6';
import {Utils} from "./utils.js";

const scenarioUtils = new ScenarioUtils('GRAPHQL_CREATE_USER');
const utils = new Utils();

export const options = scenarioUtils.getOptions();

export default function () {
    createUser();
}

function createUser() {
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const mutation = `
     mutation {
  createUser(
    input: {
      email: "${email}"
      initials: "${initials}"
      password: "${password}"
    }
  ) {
    user {
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
        'is status 200': (r) => r.status === 200,
    });
}
