import http from 'k6/http';
import * as utils from "./utils.js";
import {getRandomNumber} from "./utils.js";

const baseUrl = `https://${__ENV.HOSTNAME}/`;

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: {
        smoke: {
            executor: 'constant-arrival-rate',
            rate: 10,
            timeUnit: '1s',
            duration: '10s',
            preAllocatedVUs: 3,
            tags: {test_type: 'smoke'},
        },
        average: {
            executor: 'constant-arrival-rate',
            rate: 50,
            timeUnit: '1s',
            duration: '30s',
            preAllocatedVUs: 20,
            startTime: '10s',
            tags: {test_type: 'average'},
        },
        stress: {
            executor: 'constant-arrival-rate',
            rate: 500,
            timeUnit: '1s',
            duration: '30s',
            preAllocatedVUs: 200,
            startTime: '40s',
            tags: {test_type: 'stress'},
        },
        spike: {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: 500,
            stages: [
                {target: 5000, duration: '30s'},
                {target: 0, duration: '30s'},
            ],
            startTime: '70s',
            tags: {test_type: 'spike'},
        },
    },
    thresholds: {
        'http_req_duration{test_type:smoke}': ['p(99)<60'],
        'http_req_failed{test_type:smoke}': ['rate<0.01'],

        'http_req_duration{test_type:average}': ['p(99)<300'],
        'http_req_failed{test_type:average}': ['rate<0.01'],

        'http_req_duration{test_type:stress}': ['p(99)<3500'],
        'http_req_failed{test_type:stress}': ['rate<0.01'],

        'http_req_duration{test_type:spike}': ['p(99)<8000'],
    },
};

export default function () {
    getUsers();
}

function getUsers() {
    let page = getRandomNumber(1, 5);
    let itemsPerPage = getRandomNumber(10, 40);

    http.get(
        baseUrl + `api/users?page=${page}&itemsPerPage=${itemsPerPage}`,
        utils.getJsonHeader()
    );
}
