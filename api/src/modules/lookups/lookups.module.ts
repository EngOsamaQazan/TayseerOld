import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Lookup } from './entities/lookup.entity';
import { LookupsService } from './lookups.service';
import { LookupsController } from './lookups.controller';

@Module({
  imports: [TypeOrmModule.forFeature([Lookup])],
  providers: [LookupsService],
  controllers: [LookupsController],
  exports: [LookupsService],
})
export class LookupsModule {}
