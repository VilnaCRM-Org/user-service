import { Stack } from '@mui/material';
import React from 'react';

import { UiTypography } from '@/components';

import ApiDot from './ApiDot/ApiDot';
import styles from './styles';

function ApiInfo(): React.ReactElement {
  return (
    <>
      <Stack direction="row" alignItems="center" gap="1.5rem" sx={styles.apiHeader}>
        <UiTypography component="h2" variant="h2" sx={styles.apiTitle}>
          VilnaCRM Rest API
        </UiTypography>
        <UiTypography sx={styles.apiVersion}>1.0.6</UiTypography>
      </Stack>
      <Stack direction="row" alignItems="center" sx={styles.apiBaseUrl}>
        <UiTypography variant="medium15" sx={styles.apiUrlPart}>
          [ Base URL: petstore.swagger.io/v2 ]
        </UiTypography>
        <ApiDot />
        <UiTypography variant="medium15" sx={styles.fullUrl}>
          https://petstore.swagger.io/v2/swagger.json
        </UiTypography>
      </Stack>
      <UiTypography component="p" variant="bodyText18" sx={styles.description}>
        This is a sample server Petstore server. You can find out more about Swagger at http://
        <br style={styles.linkLineBreak} />
        swagger.io or on irc.freenode.net, #swagger. For this sample, you can use the api key
        <UiTypography component="span" variant="medium15" sx={styles.apiSpecialKey}>
          special-key
        </UiTypography>
        to test the authorization filters.
      </UiTypography>
    </>
  );
}

export default ApiInfo;
