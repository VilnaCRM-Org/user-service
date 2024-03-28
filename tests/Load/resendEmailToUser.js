import http from 'k6/http';
import {Utils} from "./utils.js";
import { check } from 'k6';

const utils = new Utils('RESEND_EMAIL')

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

    const res = http.put(
        utils.getBaseHttpUrl() + `/${id}/resend-confirmation-email`,
        null,
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
