import http from 'k6/http';
import {Utils} from "./utils.js";
import faker from "k6/x/faker";
import {check} from 'k6';

const utils = new Utils('GRAPHQL_CREATE_USER')
export const options = utils.getOptions();

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
