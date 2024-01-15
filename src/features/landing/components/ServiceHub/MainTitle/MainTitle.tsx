import { Box } from '@mui/material';
import React from 'react';

import { UiButton, UiTypography } from '@/components/ui';

import { mainTitleStyles } from './styles';

function MainTitle() {
  return (
    <Box>
      <UiTypography variant="h2" sx={mainTitleStyles.title}>
        Для кого
      </UiTypography>
      <UiTypography
        sx={mainTitleStyles.description}
        variant="bodyText18"
        pt="16px"
        maxWidth="343px"
      >
        Ми створили Vilna, орієнтуючись на специфіку сервісного бізнесу, якому
        не підходять звичайні e-commerce шаблони
      </UiTypography>
      <UiButton variant="contained" size="medium" sx={mainTitleStyles.button}>
        Спробувати
      </UiButton>
    </Box>
  );
}

export default MainTitle;
