import http from 'k6/http';
import * as utils from "./utils.js";

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    getUsers();
}

function getUsers() {
    let page = utils.getRandomNumber(1, 5);
    let itemsPerPage = utils.getRandomNumber(10, 40);

    http.get(
        utils.getBaseUrl() + `api/users?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );
}
