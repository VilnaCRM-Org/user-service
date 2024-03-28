import http from 'k6/http';
import { Utils } from "./utils.js";
import { check } from 'k6';

const utils = new Utils('GRAPHQL_RESEND_EMAIL')

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options = utils.getOptions();

export default function (data) {
    resendEmail(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function resendEmail(user) {
    const id = user.id;

    const mutation = `
     mutation{
  resendEmailToUser(input:{id:"/api/users/${id}"}){
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
        'is status 200': (r) => r.status === 200,
    });
}
