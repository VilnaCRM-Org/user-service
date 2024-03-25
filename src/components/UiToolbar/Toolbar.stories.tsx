import { Box } from '@mui/material';
import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

import UiToolbar from './index';

const meta: Meta<typeof UiToolbar> = {
  title: 'UiComponents/UiToolbar',
  component: UiToolbar,
  tags: ['autodocs'],
  argTypes: {
    children: {
      control: 'object',
    },
  },
};
export default meta;

function ToolbarComponent(): React.ReactElement {
  return (
    <Box
      sx={{
        background: 'black',
        width: '100%',
        height: '60px',
        color: 'white',
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
      }}
    >
      {t('Hello World')}
    </Box>
  );
}

type Story = StoryObj<typeof ToolbarComponent>;

export const Toolbar: Story = {
  args: {
    children: <ToolbarComponent />,
  },
};
