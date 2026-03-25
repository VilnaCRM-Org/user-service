import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLSignin';

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

export default function graphQLSignin(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const result = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(result.response, 'graphQL signin is status 200', res => res.status === 200);
  utils.checkResponse(
    result.response,
    'graphQL signin returns success',
    () => result.body?.data?.signInUser?.user?.success === true
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
