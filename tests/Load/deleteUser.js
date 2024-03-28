import http from 'k6/http';
import { Utils } from "./utils.js";
import { check } from 'k6';

const utils = new Utils('DELETE_USER')

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

    const res = http.del(
        utils.getBaseHttpUrl() + `/${id}`,
        null,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 204': (r) => r.status === 204,
    });
}
