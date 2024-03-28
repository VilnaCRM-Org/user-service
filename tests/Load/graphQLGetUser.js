import http from 'k6/http';
import {Utils} from "./utils.js";
import {check} from 'k6';

const utils = new Utils('GRAPHQL_GET_USER')

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options = utils.getOptions();

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    const query = `
      query{
  user(id: "/api/users/${id}"){
        id
        email
    }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
