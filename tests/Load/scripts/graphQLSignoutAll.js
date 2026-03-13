import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLSignoutAll';

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

export default function graphQLSignoutAll(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for graphQL signout all is status 200',
    res => res.status === 200
  );

  const accessToken = signInResult.body?.data?.signInUser?.user?.accessToken;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      signInResult.response,
      'sign-in returns access token for graphQL signout all',
      () => false
    );
    return;
  }

  const signOutAllResult = graphQLAuthFlowUtils.signOutAll(accessToken);
  utils.checkResponse(
    signOutAllResult.response,
    'graphQL signout all is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    signOutAllResult.response,
    'graphQL signout all returns success',
    () => signOutAllResult.body?.data?.signOutAllUser?.user?.success === true
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
