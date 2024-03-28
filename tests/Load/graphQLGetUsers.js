import http from 'k6/http';
import {Utils} from "./utils.js";
import { check } from 'k6';

const utils = new Utils('GRAPHQL_GET_USERS')

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options = utils.getOptions();

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
