import { Stack } from '@mui/material';
import React from 'react';

import UITypography from '@/components/ui/UITypography/UITypography';

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
      <UITypography variant="demi18" sx={{ color: '#1B2327' }}>
        info@vilnacrm.com
      </UITypography>
    </Stack>
  );
}

export default Gmail;
