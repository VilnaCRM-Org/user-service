import spec from './spec.yaml';

import SwaggerUI from 'swagger-ui-react';

function ApiDocumentation(): React.ReactElement {
  return <SwaggerUI spec={spec} />;
}

export default ApiDocumentation;
