import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLSignout';

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

export default function graphQLSignout(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for graphQL signout is status 200',
    res => res.status === 200
  );

  const accessToken = signInResult.body?.data?.signInUser?.user?.accessToken;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      signInResult.response,
      'sign-in returns access token for graphQL signout',
      () => false
    );
    return;
  }

  const signOutResult = graphQLAuthFlowUtils.signOut(accessToken);
  utils.checkResponse(
    signOutResult.response,
    'graphQL signout is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    signOutResult.response,
    'graphQL signout returns success',
    () => signOutResult.body?.data?.signOutUser?.user?.success === true
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
