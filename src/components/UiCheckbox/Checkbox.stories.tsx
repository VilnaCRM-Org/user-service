import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

import UiCheckbox from './index';

const meta: Meta<typeof UiCheckbox> = {
  title: 'UiComponents/UiCheckbox',
  component: UiCheckbox,
  tags: ['autodocs'],
  argTypes: {
    disabled: {
      type: 'boolean',
      description: 'Whether the checkbox is disabled',
      control: { type: 'boolean' },
    },
    label: {
      type: 'string',
      description: 'Label for the checkbox',
    },
    onChange: {
      type: 'function',
      description: 'Callback function when the checkbox is changed',
    },
    error: {
      type: 'boolean',
      description: 'Whether the checkbox is in error state',
      control: { type: 'boolean' },
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiCheckbox>;

export const Checkbox: Story = {
  args: {
    error: false,
    label: t('Checkbox label text'),
  },
};
