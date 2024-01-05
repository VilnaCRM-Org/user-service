import { Stack } from '@mui/material';
import React from 'react';

import { UiTypography } from '@/components/ui';

function Gmail() {
  return (
    <Stack
      alignItems="center"
      sx={{
        width: '100%',
        padding: '15px 16px',
        borderRadius: '8px',
        background: '#fff',
        border: '1px solid  #D0D4D8',
      }}
    >
      <UiTypography variant="demi18" sx={{ color: '#1B2327' }}>
        info@vilnacrm.com
      </UiTypography>
    </Stack>
  );
}

export default Gmail;
