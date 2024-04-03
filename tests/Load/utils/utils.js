import {check} from 'k6';

export class Utils{
    constructor() {
        const host = this.getConfig().apiHost;

        this.baseUrl = `https://${host}/api`;
        this.baseHttpUrl = this.baseUrl + '/users';
        this.baseGraphQLUrl = this.baseUrl + '/graphql';
        this.graphQLIdPrefix = '/api/users/';
    }

    getConfig(){
        return JSON.parse(open('config.json'));
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

    getGraphQLIdPrefix(){
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

    getCLIVariable(variable){
        return `${__ENV[variable]}`
    }

    checkUserIsDefined(user){
        check(user, {
            'user is defined': (u) =>
                u !== undefined,
        });
    }
}