import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLRequestPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const graphQLAuthFlowUtils = new GraphQLAuthFlowUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users,
  };
}

export const options = scenarioUtils.getOptions();

export default function graphQLRequestPasswordReset(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const result = graphQLAuthFlowUtils.requestPasswordReset(user.email);
  utils.checkResponse(
    result.response,
    'graphQL request password reset is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    result.response,
    'graphQL request password reset returns empty user payload',
    () =>
      result.body?.data?.requestPasswordResetUser !== undefined &&
      result.body.data.requestPasswordResetUser.user === null
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
