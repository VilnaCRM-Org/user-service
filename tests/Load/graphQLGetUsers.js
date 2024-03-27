import http from 'k6/http';
import {utils} from "./utils.js";
import { check } from 'k6';

export function setup() {
    utils.insertUsers(
        Number(
            utils.getFromEnv('LOAD_TEST_USERS_TO_INSERT')
        )
    )
}

export const options = utils.getOptions('GRAPHQL_GET_USERS');

export default function () {
    getUsers();
}

function getUsers() {
    let num = utils.getRandomNumber(1, 10000);

    const query = `
        query{
        users(first: ${num}){
            edges{
                node{
                    id
                }
            }
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
