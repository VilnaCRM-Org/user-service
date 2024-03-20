import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

import UiInput from './index';

const meta: Meta<typeof UiInput> = {
  title: 'UiComponents/UiInput',
  component: UiInput,
  tags: ['autodocs'],
  argTypes: {
    placeholder: {
      type: 'string',
      description: 'Placeholder text for the input',
      control: { type: 'text' },
    },
    value: {
      type: 'string',
      description: 'Value of the input element',
      control: { type: 'text' },
    },
    disabled: {
      type: 'boolean',
      description: 'Whether the input is disabled',
      control: { type: 'boolean' },
    },
    type: {
      type: 'string',
      description: 'Type of the input element (e.g., text, password)',
      options: ['text', 'password', 'email', 'number'],
      control: { type: 'radio' },
    },
    error: {
      type: 'boolean',
      description: 'Whether the input is in error state',
      control: { type: 'boolean' },
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiInput>;

export const Input: Story = {
  args: {
    placeholder: t('Input'),
    error: false,
  },
};
