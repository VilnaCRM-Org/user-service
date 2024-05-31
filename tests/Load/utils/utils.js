import { check } from 'k6';
import http from 'k6/http';
import faker from 'k6/x/faker';

export default class Utils {
    constructor() {
        const host = this.getConfig().apiHost;

        this.baseUrl = `https://${host}/api`;
        this.baseHttpUrl = this.baseUrl + '/users';
        this.baseGraphQLUrl = this.baseUrl + '/graphql';
        this.graphQLIdPrefix = '/api/users/';
    }

    getConfig() {
        try {
            return JSON.parse(open('../config.json'));
        } catch (error) {
            return JSON.parse(open('../config.json.dist'));
        }
    }

    getBaseUrl() {
        return this.baseUrl;
    }

    getBaseHttpUrl() {
        return this.baseHttpUrl;
    }

    getBaseGraphQLUrl() {
        return this.baseGraphQLUrl;
    }

    getGraphQLIdPrefix() {
        return this.graphQLIdPrefix;
    }

    getJsonHeader() {
        return {
            headers: {
                'Content-Type': 'application/json',
            },
        };
    }

    getMergePatchHeader() {
        return {
            headers: {
                'Content-Type': 'application/merge-patch+json',
            },
        };
    }

    getRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    getCLIVariable(variable) {
        return `${__ENV[variable]}`;
    }

    checkUserIsDefined(user) {
        check(user, {
            'user is defined': (u) =>
                u !== undefined,
        });
    }

    generateUser(){
        const email = `${faker.number.int32()}${faker.person.email()}`;
        const initials = faker.person.name();
        const password = faker.internet.password(true, true, true, false, false, 60);

        return {
            email,
            password,
            initials,
        };
    }

   checkResponse(response, checkName, checkFunction) {
        check(response, {
            [checkName]: (res) => checkFunction(res),
        });
    }

    registerUser(user){
        const payload = JSON.stringify(user);

        return http.post(
            this.getBaseHttpUrl(),
            payload,
            this.getJsonHeader()
        );
    }
}