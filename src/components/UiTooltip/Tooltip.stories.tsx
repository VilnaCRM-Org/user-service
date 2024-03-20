import type { Meta, StoryObj } from '@storybook/react';
import { t } from 'i18next';

import UiTooltip from '.';

const meta: Meta<typeof UiTooltip> = {
  title: 'UiComponents/UITooltip',
  component: UiTooltip,
  tags: ['autodocs'],
  argTypes: {
    children: {
      type: 'string',
      name: 'children',
      description: 'Text of the button',
    },
    placement: {
      type: 'string',
      description: 'Placement of the tooltip',
      options: ['top', 'bottom', 'left', 'right'],
      control: { type: 'radio' },
    },
    arrow: {
      type: 'boolean',
      description: 'Whether the tooltip has an arrow',
      control: { type: 'boolean' },
    },
    title: {
      type: 'string',
      description: 'Content of the tooltip',
    },
  },
};

export default meta;

type Story = StoryObj<typeof UiTooltip>;

export const Tooltip: Story = {
  args: {
    children: t('Hello World!'),
    placement: 'bottom',
    arrow: true,
    title: 'UiTooltip',
  },
};
