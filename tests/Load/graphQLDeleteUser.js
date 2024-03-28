import http from 'k6/http';
import { Utils } from "./utils.js";
import { check } from 'k6';

const utils = new Utils('GRAPHQL_DELETE_USER')

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options= utils.getOptions();

export default function (data) {
    deleteUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function deleteUser(user) {
    const id = user.id;

    const mutation = `
     mutation{
        deleteUser(input:{
            id: "${id}"
        }){
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
