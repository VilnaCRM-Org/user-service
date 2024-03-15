import {SharedArray} from 'k6/data';

const users = new SharedArray('users.json', function () {
    return JSON.parse(open('./users.json')).users;
});

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

export function getRandomNumber(min, max) {
    return Math.floor(Math.random() * (max - min+ 1)) + min;
}