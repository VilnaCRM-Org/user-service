import type { Meta, StoryObj } from '@storybook/react';

import { ServicesTooltip } from './index';

const meta: Meta<typeof ServicesTooltip> = {
  title: 'UiComponents/UITooltip',
  component: ServicesTooltip,
  tags: ['autodocs'],
  argTypes: {
    children: {
      type: 'string',
      name: 'label',
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

type Story = StoryObj<typeof ServicesTooltip>;

export const Services: Story = {
  args: {
    children: 'Services',
    placement: 'bottom',
    arrow: true,
    title: 'Services',
  },
};
