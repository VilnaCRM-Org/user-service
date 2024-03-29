import {Env} from "./env.js";

export class Utils{
    constructor() {
        const env = new Env();
        const host = env.get('LOAD_TEST_API_HOST');

        this.baseUrl = `https://${host}/api`;
        this.baseHttpUrl = this.baseUrl + '/users';
        this.baseGraphQLUrl = this.baseUrl + '/graphql';
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
}