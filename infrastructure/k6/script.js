import http from 'k6/http';
import { sleep } from 'k6';

const baseUrl = `https://${__ENV.HOSTNAME}/`;

export const options = {
  thresholds: {
    // Assert that 99% of requests finish within 1000ms.
    http_req_duration: ["p(99) < 1000"],
  },
  // Set insecureSkipTLSVerify to true to skip SSL/TLS verification
  insecureSkipTLSVerify: true,
};

export default function() {
  getUser();
  getUsers();
  createUser();
  deleteUser();
  updateUser();
  replaceUser();
  confirmUser();
  resendEmailToUser();
  sleep(1);
}

function confirmUser(){
  const payload = JSON.stringify({
    token: 'aaa',
  });
  http.post(baseUrl + '/api/users', payload);
}

function deleteUser(){
  http.del(baseUrl + 'api/users/someId')
}

function updateUser(){
  const payload = JSON.stringify({
    email: 'aaa',
    newPassword: 'bbb',
    initials: 'ccc',
    oldPassword: 'ddd',
  });
  http.patch(baseUrl + '/api/users/someId', payload);
}

function replaceUser(){
  const payload = JSON.stringify({
    email: 'aaa',
    newPassword: 'bbb',
    initials: 'ccc',
    oldPassword: 'ddd',
  });
  http.put(baseUrl + '/api/users/someId', payload);
}

function resendEmailToUser(){
  http.post(baseUrl + 'api/users/someId/resend-confirmation-email');
}

function getUser() {
  http.get(baseUrl + 'api/users/someId');
}

function getUsers() {
  http.get(baseUrl + '/api/users?page=1&itemsPerPage=30');
}

function createUser() {
  const payload = JSON.stringify({
    email: 'aaa',
    password: 'bbb',
    initials: 'ccc',
  });
  http.post(baseUrl + '/api/users', payload);
}

