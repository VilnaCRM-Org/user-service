import SwaggerUI from 'swagger-ui-react';

function ApiDocumentation(): React.ReactElement {
  return <SwaggerUI url="https://petstore.swagger.io/v2/swagger.json" />;
}

export default ApiDocumentation;
