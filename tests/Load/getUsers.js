import http from 'k6/http';
import {Utils} from "./utils.js";
import { check } from 'k6';

const utils = new Utils('GET_USERS')

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
    let page = utils.getRandomNumber(1, 5);
    let itemsPerPage = utils.getRandomNumber(10, 40);

    const res = http.get(
        utils.getBaseHttpUrl() + `?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
