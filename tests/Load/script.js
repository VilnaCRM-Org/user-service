import http from 'k6/http';
import { sleep } from 'k6';

const baseUrl = `https://${__ENV.HOSTNAME}/`;

export const options = {
  thresholds: {
    // Assert that 99% of requests finish within 200ms.
    http_req_duration: ["p(99) < 200"],
  },
  // Set insecureSkipTLSVerify to true to skip SSL/TLS verification
  insecureSkipTLSVerify: true,
};

export default function() {
  getUser();
  // getUsers();
  // createUser();
  // deleteUser();
  // updateUser();
  // replaceUser();
  // confirmUser();
  // resendEmailToUser();
  // sleep(1);
}

function confirmUser(){
  const payload = JSON.stringify({
    token: 'aaa',
  });
  http.post(baseUrl + '/api/users', payload, {
    tags: { name: 'Confirm User' },
  });
}

function deleteUser(){
  let id = 'someId';
  http.del(baseUrl + 'api/users/${id}', {
    tags: { name: 'Delete User' },
  })
}

function updateUser(){
  let id = 'someId';
  const payload = JSON.stringify({
    email: 'aaa',
    newPassword: 'bbb',
    initials: 'ccc',
    oldPassword: 'ddd',
  });
  http.patch(baseUrl + '/api/users/${id}', payload, {
    tags: { name: 'Update User' },
  });
}

function replaceUser(){
  let id = 'someId';
  const payload = JSON.stringify({
    email: 'aaa',
    newPassword: 'bbb',
    initials: 'ccc',
    oldPassword: 'ddd',
  });
  http.put(baseUrl + '/api/users/${id}', payload, {
    tags: { name: 'Replace User' },
  });
}

function resendEmailToUser(){
  let id = 'someId';
  http.post(baseUrl + 'api/users/${id}/resend-confirmation-email', {
    tags: { name: 'Resend Email User' },
  });
}

function getUser() {
  let id = '018e3d1c-d163-71b8-a326-6129f9d4b95d';
  http.get(baseUrl + 'api/users/${id}', {
    tags: { name: 'Get User' },
  });
}

function getUsers() {
  let page = '1';
  let itemsPerPage = '30';
  http.get(baseUrl + '/api/users?page=${page}&itemsPerPage=${itemsPerPage}', {
    tags: { name: 'Get Users' },
  });
}

function createUser() {
  const payload = JSON.stringify({
    email: 'aaa',
    password: 'bbb',
    initials: 'ccc',
  });
  http.post(baseUrl + '/api/users', payload, {
    tags: { name: 'Create User' },
  });
}

