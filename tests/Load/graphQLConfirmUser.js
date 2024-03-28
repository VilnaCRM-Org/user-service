import http from 'k6/http';
import {Utils} from "./utils.js";
import faker from "k6/x/faker";
import {check} from 'k6';

const utils = new Utils('GRAPHQL_CONFIRM_USER')

export default function () {
    confirmUser();
}

async function confirmUser() {
    const email = faker.person.email();
    const userResponse = await createUser(email);

    let token = null;

    if (userResponse.status === 201) {
        token = await utils.getConfirmationToken(email);
    }

    const mutation = `
     mutation {
  confirmUser(input: { token: "${token}" }) {
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

function createUser(email) {
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    return http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );
}
