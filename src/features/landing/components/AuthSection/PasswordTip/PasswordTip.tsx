import { Stack } from '@mui/material';

import { UiTypography } from '@/components';
import { colorTheme } from '@/components/UiColorTheme';

function PasswordTip(): React.ReactElement {
  return (
    <Stack direction="column" gap="0.25rem">
      <UiTypography
        variant="medium14"
        sx={{
          pb: '2px',
          borderBottom: `2px solid ${colorTheme.palette.grey400.main}`,
        }}
      >
        Не менше 8 символів.
      </UiTypography>
      <UiTypography variant="medium14" maxWidth="10rem">
        Рекомендуємо використовувати:
      </UiTypography>
      <ul
        style={{
          display: 'flex',
          flexDirection: 'column',
          gap: '0.25rem',
          marginLeft: '1rem',
        }}
      >
        <li
          style={{
            fontSize: '0.8rem',
            color: colorTheme.palette.darkPrimary.main,
            fontWeight: 400,
          }}
        >
          малі та великі літери
        </li>
        <li
          style={{
            fontSize: '0.8rem',
            fontWeight: 400,
            color: colorTheme.palette.darkPrimary.main,
          }}
        >
          спеціальні символи(#&*$)
        </li>
        <li
          style={{
            fontSize: '0.8rem',
            fontWeight: 400,
            color: colorTheme.palette.darkPrimary.main,
          }}
        >
          використовувати цифри
        </li>
      </ul>
    </Stack>
  );
}

export default PasswordTip;
