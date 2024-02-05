import type { Meta, StoryObj } from '@storybook/react';

import UiButton from './index';

const meta: Meta<typeof UiButton> = {
  title: 'UiComponents/UiButton',
  component: UiButton,
  tags: ['autodocs'],
  argTypes: {
    size: {
      type: 'string',
      description: 'Size of the button',
      options: ['small', 'medium'],
      control: { type: 'radio' },
    },
    children: {
      type: 'string',
      name: 'label',
      description: 'Text of the button',
    },
    type: {
      type: 'string',
      description: 'Type of the button',
      options: ['button', 'submit'],
      control: { type: 'radio' },
    },
    disabled: {
      type: 'boolean',
      description: 'Whether the button is disabled',
      control: { type: 'boolean' },
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiButton>;

export const Contained: Story = {
  args: {
    children: 'Primary Button',
    variant: 'contained',
    size: 'small',
  },
};
export const Outlined: Story = {
  args: {
    children: 'Outlined Button',
    variant: 'outlined',
    size: 'small',
  },
};
