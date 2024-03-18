import {SharedArray} from 'k6/data';

const users = new SharedArray('users.json', function () {
    return JSON.parse(open('./users.json')).users;
});

const baseUrl = `https://${__ENV.HOSTNAME}/`;

const smokeTestDuration = 10;
const averageTestDuration = 30;
const stressTestDuration = 30;
const spikeTestDurationRise = 30;
const spikeTestDurationDown = 15;

const smokeThreshold = parseInt(`${__ENV.SMOKE_THRESHOLD}`, 10);
const averageThreshold = parseInt(`${__ENV.AVERAGE_THRESHOLD}`, 10);
const stressThreshold = parseInt(`${__ENV.STRESS_THRESHOLD}`, 10);
const spikeThreshold = parseInt(`${__ENV.SPIKE_THRESHOLD}`, 10);

export function getScenarios(
    smokeRatePerSecond = 10,
    averageRatePerSecond = 50,
    stressRatePerSecond = 500,
    spikeTargetRatePerSecond = 5000,
) {
    return {
        smoke: getSmokeScenario(smokeRatePerSecond),
        average: getAverageScenario(averageRatePerSecond),
        stress: getStressScenario(stressRatePerSecond),
        spike: getSpikeScenario(spikeTargetRatePerSecond),
    }
}

export function getThresholds() {
    return {
        'http_req_duration{test_type:smoke}': ['p(99)<' + smokeThreshold],
        'http_req_duration{test_type:average}': ['p(99)<' + averageThreshold],
        'http_req_duration{test_type:stress}': ['p(99)<' + stressThreshold],
        'http_req_duration{test_type:spike}': ['p(99)<' + spikeThreshold],
        'http_req_failed{test_type:smoke}': ['rate<0.01'],
        'http_req_failed{test_type:average}': ['rate<0.01'],
        'http_req_failed{test_type:stress}': ['rate<0.1'],
    }
}

function getSmokeScenario(ratePerSecond) {
    return {
        executor: 'constant-arrival-rate',
        rate: ratePerSecond,
        timeUnit: '1s',
        duration: smokeTestDuration + 's',
        preAllocatedVUs: 3,
        tags: {test_type: 'smoke'},
    }
}

function getAverageScenario(ratePerSecond) {
    return {
        executor: 'constant-arrival-rate',
        rate: ratePerSecond,
        timeUnit: '1s',
        duration: averageTestDuration + 's',
        preAllocatedVUs: 20,
        startTime: smokeTestDuration + 's',
        tags: {test_type: 'average'},
    }
}

function getStressScenario(ratePerSecond) {
    return {
        executor: 'constant-arrival-rate',
        rate: ratePerSecond,
        timeUnit: '1s',
        duration: stressTestDuration + 's',
        preAllocatedVUs: 200,
        startTime: smokeTestDuration + averageTestDuration + 's',
        tags: {test_type: 'stress'},
    }
}

function getSpikeScenario(targetRatePerSecond) {
    return {
        executor: 'ramping-arrival-rate',
        startRate: 0,
        timeUnit: '1s',
        preAllocatedVUs: 500,
        stages: [
            {
                target: targetRatePerSecond,
                duration: spikeTestDurationRise + 's'
            },
            {target: 0, duration: spikeTestDurationDown + 's'},
        ],
        startTime: smokeTestDuration + averageTestDuration + stressTestDuration + 's',
        tags: {test_type: 'spike'},
    }
}

export function getUsers() {
    return users;
}

export function getRandomUser() {
    return users[Math.floor(Math.random() * users.length)];
}

export function getJsonHeader() {
    return {
        headers: {
            'Content-Type': 'application/json',
        },
    };
}

export function getMergePatchHeader() {
    return {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    };
}

export function getRandomNumber(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

export function getBaseUrl(){
    return baseUrl;
}

export function generateRandomEmail() {
    const characters = 'abcdefghijklmnopqrstuvwxyz0123456789';

    let localPart = '';
    const localPartLength = Math.floor(Math.random() * 6) + 5;
    for (let i = 0; i < localPartLength; i++) {
        localPart += characters.charAt(Math.floor(Math.random() * characters.length));
    }

    return localPart + '@example.com';
}
