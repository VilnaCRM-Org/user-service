module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'check-task-number-rule': [2, 'always'],
  },
  plugins: [
    {
      rules: {
        'check-task-number-rule': data => {
          const list = 'build|chore|ci|docs|feat|fix|perf|refactor|revert|style|test';

          const regexp = new RegExp(`(${list})(.#(\\d+)).:`, 'gm');

          const taskNumber = data.header.match(regexp);

          const correctCommit = data.header.includes(taskNumber) || false;

          return [correctCommit, `your task number incorrect (${this.list}(#1))`];
        },
      },
    },
  ],
};
