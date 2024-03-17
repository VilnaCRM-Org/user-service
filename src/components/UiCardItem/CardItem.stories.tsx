import type { Meta, StoryObj } from '@storybook/react';

import WhyUsTemplatesIcon from '../../features/landing/assets/svg/why-us/templates.svg';
import UiCardList from '../UiCardList';
import { CardList } from '../UiCardList/types';

const meta: Meta<typeof UiCardList> = {
  title: 'UiComponents/UiCardItem',
  component: UiCardList,
  tags: ['autodocs'],
  argTypes: {},
};

export default meta;

function CardItem(args: CardList): React.ReactElement {
  return <UiCardList {...args} />;
}

type Story = StoryObj<typeof CardItem>;

export const CardItemLarge: Story = {
  args: {
    cardList: [
      {
        type: 'largeCard',
        id: 'card-item-1',
        imageSrc: WhyUsTemplatesIcon,
        title: 'Open source',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_open_source',
      },
    ],
  },
};
export const CardItemSmall: Story = {
  args: {
    cardList: [
      {
        type: 'smallCard',
        id: 'card-item-1',
        imageSrc: WhyUsTemplatesIcon,
        title: 'Open source',
        text: 'Для випадків, коли ви не знайшли потрібну готову інтеграцію',
        alt: 'why_us.alt_image.alt_open_source',
      },
    ],
  },
};
